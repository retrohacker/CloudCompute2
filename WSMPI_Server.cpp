/*
 * WSMPI_Server.cpp
 *
 *  Created on: Jun 24, 2012
 *      Author: crackers
 */

#include "WSMPI_Server.h"

WSMPI_Server::WSMPI_Server(char* address, int port) {
	//TODO verify the data passed in
	MasterSocketInfo.sin_family = AF_INET;
	MasterSocketInfo.sin_port = htons(port);
	inet_pton(AF_INET, address, &MasterSocketInfo.sin_addr);

	//Zero out the rest of the structure
	for(int i=0;i<8;i++) {
		MasterSocketInfo.sin_zero[i] = 0;
	}
}

WSMPI_Server::~WSMPI_Server() {
	// TODO Ensure memory is deallocated upon going out of scope
}

int WSMPI_Server::start() {
	//TODO add error checking and handling
	MasterSocket = socket(AF_INET, SOCK_STREAM, 0);
	//Allow the server to reuse ports
	int reuseyes=1;

	// lose the pesky "Address already in use" error message
	if (setsockopt(MasterSocket,SOL_SOCKET,SO_REUSEADDR,&reuseyes,sizeof(int)) == -1) {
		return -1;
	}
	char buf[INET_ADDRSTRLEN];
	inet_ntop(AF_INET, &MasterSocketInfo.sin_addr, buf, INET_ADDRSTRLEN);
	std::cout << "Starting server at " << buf <<
			" on port " << ntohs(MasterSocketInfo.sin_port) << ": ";
	int b = bind(MasterSocket, (sockaddr*)&MasterSocketInfo, sizeof(sockaddr));
	int li = listen(MasterSocket,5);
	//Check if successful
	if(b == -1 || li == -1) {
		std::cout << strerror( errno ) << "\n";
		//return -1;
	} else {
		std::cout << "Success!\n";
		//return 0;
	}
	while(true) {
		sockaddr_in node; //Info about socket i think
		unsigned int sin_size = sizeof(node);
		int resp = accept(MasterSocket,(sockaddr*)&node,&sin_size);
		if(resp==-1) {
			std::cout << "fail" << "\n";
		}
		else {
			Client *newC = new Client(resp);
			this->Connecting.push_back(newC);
			for(std::list<Client *>::iterator it = Connecting.begin(); it!=Connecting.end();it++) {
				std::cout << (*it)->getID() << "\n";
			}
		}
	}
}
