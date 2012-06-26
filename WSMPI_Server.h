/*
 * WSMPI_Server.h
 *
 *  Created on: Jun 24, 2012
 *      Author: crackers
 */
#include <sys/types.h>
#include <sys/socket.h>
#include <arpa/inet.h>
#include <iostream>
#include <errno.h>
#include <string.h>
#include <string>
#include <list>
#include "Client.h"

#ifndef WSMPI_SERVER_H_
#define WSMPI_SERVER_H_

class WSMPI_Server {
private:
	//Stores the ID of the master socket
	int MasterSocket;
	std::list<Client *> Nodes;
	std::list<Client *> Connecting;

	//Used to construct the master socket
	sockaddr_in MasterSocketInfo;

public:
	WSMPI_Server(char *address, int port);
	int start();
	virtual ~WSMPI_Server();
};

#endif /* WSMPI_SERVER_H_ */
